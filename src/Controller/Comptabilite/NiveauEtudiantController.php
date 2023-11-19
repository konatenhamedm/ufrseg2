<?php

namespace App\Controller\Comptabilite;

use App\Entity\NiveauEtudiant;
use App\Form\NiveauEtudiantType;
use App\Repository\NiveauEtudiantRepository;
use App\Service\ActionRender;
use App\Service\FormError;
use App\Service\Omines\Column\NumberFormatColumn;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\BoolColumn;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTableFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\Registry;

#[Route('/comptabilite/niveau/etudiant')]
class NiveauEtudiantController extends AbstractController
{
    private $workflow;
    private $security;
    /**
     * Undocumented variable
     *
     * @var \App\Entity\Utilisateur
     */
    private $user;
    public function __construct( Security $security,Registry $workflow)
    {
        $this->workflow = $workflow;
        $this->security = $security;
        $this->user = $security->getUser()->getPersonne();
    }

    #[Route('/{etat}', name: 'app_comptabilite_niveau_etudiant_preinscription_index', methods: ['GET', 'POST'])]
    public function indexPreinscription(Request $request, DataTableFactory $dataTableFactory,$etat): Response
    {  $titre = '';
        if($etat == "attente_validation"){
            $titre = "Liste des préinscriptions en attente de validation";
        }elseif($etat == "valide_non_paye"){
            $titre = "Liste des préinscriptions validées";
        }else{
            $titre = "Liste des préinscriptions réjétées";
        }
//dd($this->user->getId());
        $table = $dataTableFactory->create()
            //->add('nom', TextColumn::class, ['field' => 'etudiant.getNomComplet', 'label' => 'Nom et Prénoms'])
            ->add('date', DateTimeColumn::class, ['label' => 'Date demande','format' => 'd-m-Y', ])
            ->add('dateValidation', DateTimeColumn::class, ['label' => 'Date validation','format' => 'd-m-Y' ])
            ->add('filiere', TextColumn::class, ['label' => 'Filiere', 'field'=>'filiere.libelle'])
            ->add('niveau', TextColumn::class, ['label' => 'Niveau', 'field'=>'niveau.libelle'])
            ->add('montant', NumberFormatColumn::class, ['label' => 'Mnt. Préinscr.', 'field'=>'filiere.montantPreinscription'])
            ->createAdapter(ORMAdapter::class, [
                'entity' => NiveauEtudiant::class,
                'query' => function(QueryBuilder $qb) use ($etat){
                    $qb->select('e, filiere, etudiant,niveau')
                        ->from(NiveauEtudiant::class, 'e')
                        ->join('e.etudiant', 'etudiant')
                        ->join('e.filiere', 'filiere')
                        ->join('e.niveau', 'niveau')
                        ->andWhere('etudiant.id = :etudiant')
                        ->andWhere('e.etat = :statut')
                        ->setParameter('statut',$etat)
                        ->setParameter('etudiant',$this->user->getId())
                    ;
                }
            ])
            ->setName('dt_app_comptabilite_niveau_etudiant_preinscription'.$etat);

        $renders = [
            'edit' =>  new ActionRender(function () {
                return true;
            }),
            'delete' => new ActionRender(function () {
                return true;
            }),
        ];


        $hasActions = false;

        foreach ($renders as $_ => $cb) {
            if ($cb->execute()) {
                $hasActions = true;
                break;
            }
        }

        if ($hasActions) {
            $table->add('id', TextColumn::class, [
                'label' => 'Actions'
                , 'orderable' => false
                ,'globalSearchable' => false
                ,'className' => 'grid_row_actions'
                , 'render' => function ($value, NiveauEtudiant $context) use ($renders) {
                    $options = [
                        'default_class' => 'btn btn-sm btn-clean btn-icon mr-2 ',
                        'target' => '#modal-lg',

                        'actions' => [
                            'edit' => [
                                'url' => $this->generateUrl('app_comptabilite_niveau_etudiant_edit', ['id' => $value]),
                                'ajax' => true,
                                'stacked' => false,
                                'icon' => '%icon% bi bi-pen',
                                'attrs' => ['class' => 'btn-main'],
                                'render' => $renders['edit']
                            ],
                            'delete' => [
                                'target' => '#modal-small',
                                'url' => $this->generateUrl('app_comptabilite_niveau_etudiant_delete', ['id' => $value]),
                                'ajax' => true,
                                'stacked' => false,
                                'icon' => '%icon% bi bi-trash',
                                'attrs' => ['class' => 'btn-danger'],
                                'render' => $renders['delete']
                            ]
                        ]

                    ];
                    return $this->renderView('_includes/default_actions.html.twig', compact('options', 'context'));
                }
            ]);
        }


        $table->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }


        return $this->render('etudiant/preinscription/index.html.twig', [
            'datatable' => $table,
            'etat'=>$etat,
              'titre'=> $titre ,
        ]);
    }

    #[Route('/', name: 'app_comptabilite_niveau_etudiant_index', methods: ['GET', 'POST'])]
    public function index(Request $request, DataTableFactory $dataTableFactory): Response
    {
        $table = $dataTableFactory->create()
            ->add('nom', TextColumn::class, ['field' => 'etudiant.getNomComplet', 'label' => 'Nom et Prénoms'])
            ->add('dateNaissance', DateTimeColumn::class, ['label' => 'Date de naissance','format' => 'd-m-Y', 'field'=>'etudiant.dateNaissance'])
            ->add('filiere', TextColumn::class, ['label' => 'Filiere', 'field'=>'filiere.libelle'])
            ->add('date', DateTimeColumn::class, ['label' => 'Date de demande','format' => 'd-m-Y',])
            //->add('montantPreinscription', NumberFormatColumn::class, ['label' => 'Mnt. Préinscr.'])
        ->createAdapter(ORMAdapter::class, [
            'entity' => NiveauEtudiant::class,
            'query' => function(QueryBuilder $qb){
                $qb->select('e, filiere, etudiant')
                    ->from(NiveauEtudiant::class, 'e')
                    ->join('e.etudiant', 'etudiant')
                    ->join('e.filiere', 'filiere')
                    ->andWhere('e.etat = :statut')
                    ->setParameter('statut','attente_validation')
                ;
            }
        ])
        ->setName('dt_app_comptabilite_niveau_etudiant');

        $renders = [
            'edit' =>  new ActionRender(function () {
                return true;
            }),
            'delete' => new ActionRender(function () {
                return true;
            }),
        ];


        $hasActions = false;

        foreach ($renders as $_ => $cb) {
            if ($cb->execute()) {
                $hasActions = true;
                break;
            }
        }

        if ($hasActions) {
            $table->add('id', TextColumn::class, [
                'label' => 'Actions'
                , 'orderable' => false
                ,'globalSearchable' => false
                ,'className' => 'grid_row_actions'
                , 'render' => function ($value, NiveauEtudiant $context) use ($renders) {
                    $options = [
                        'default_class' => 'btn btn-sm btn-clean btn-icon mr-2 ',
                        'target' => '#modal-lg',

                        'actions' => [
                            'edit' => [
                                'url' => $this->generateUrl('app_comptabilite_niveau_etudiant_edit', ['id' => $value]),
                                'ajax' => true,
                                'stacked' => false,
                                'icon' => '%icon% bi bi-pen',
                                'attrs' => ['class' => 'btn-main'],
                                'render' => $renders['edit']
                        ],
                        'delete' => [
                            'target' => '#modal-small',
                            'url' => $this->generateUrl('app_comptabilite_niveau_etudiant_delete', ['id' => $value]),
                            'ajax' => true,
                            'stacked' => false,
                            'icon' => '%icon% bi bi-trash',
                            'attrs' => ['class' => 'btn-danger'],
                            'render' => $renders['delete']
                        ]
                    ]

                    ];
                    return $this->renderView('_includes/default_actions.html.twig', compact('options', 'context'));
                }
            ]);
        }


        $table->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }


        return $this->render('comptabilite/niveau_etudiant/index.html.twig', [
            'datatable' => $table
        ]);
    }

    #[Route('/{etat}', name: 'app_comptabilite_niveau_etudiant_valider_index', methods: ['GET', 'POST'])]
    public function indexValider(Request $request,$etat, DataTableFactory $dataTableFactory): Response
    {
        $table = $dataTableFactory->create()
            ->add('code', TextColumn::class, ['label' => 'Code'])
            ->add('nom', TextColumn::class, ['field' => 'etudiant.getNomComplet', 'label' => 'Nom et Prénoms'])
            ->add('dateNaissance', DateTimeColumn::class, ['label' => 'Date de naissance','format' => 'd-m-Y', 'field'=>'etudiant.dateNaissance'])
            ->add('filiere', TextColumn::class, ['label' => 'Filiere', 'field'=>'filiere.libelle'])
            ->add('date', DateTimeColumn::class, ['label' => 'Date de demande','format' => 'd-m-Y',])
            ->add('dateValidation', DateTimeColumn::class, ['label' => 'Date de validation','format' => 'd-m-Y',])
            //->add('montantPreinscription', NumberFormatColumn::class, ['label' => 'Mnt. Préinscr.'])
            ->createAdapter(ORMAdapter::class, [
                'entity' => NiveauEtudiant::class,
                'query' => function(QueryBuilder $qb) use ($etat){
                    $qb->select('e, filiere, etudiant')
                        ->from(NiveauEtudiant::class, 'e')
                        ->join('e.etudiant', 'etudiant')
                        ->join('e.filiere', 'filiere')
                        ->andWhere('e.etat = :statut')
                        ->setParameter('statut',$etat)
                    ;
                }
            ])
            ->setName('dt_app_comptabilite_niveau_etudiant_valider'.$etat);

        $renders = [
            'edit' =>  new ActionRender(function () {
                return true;
            }),
            'delete' => new ActionRender(function () {
                return true;
            }),
        ];


        $hasActions = false;

        foreach ($renders as $_ => $cb) {
            if ($cb->execute()) {
                $hasActions = true;
                break;
            }
        }

        if ($hasActions) {
            $table->add('id', TextColumn::class, [
                'label' => 'Actions'
                , 'orderable' => false
                ,'globalSearchable' => false
                ,'className' => 'grid_row_actions'
                , 'render' => function ($value, NiveauEtudiant $context) use ($renders) {
                    $options = [
                        'default_class' => 'btn btn-sm btn-clean btn-icon mr-2 ',
                        'target' => '#modal-lg',

                        'actions' => [
                            'edit' => [
                                'url' => $this->generateUrl('app_comptabilite_niveau_etudiant_payer', ['id' => $value]),
                                'ajax' => true,
                                'stacked' => false,
                                'icon' => '%icon% bi bi-pen',
                                'attrs' => ['class' => 'btn-main'],
                                'render' => $renders['edit']
                            ],
                           /* 'delete' => [
                                'target' => '#modal-small',
                                'url' => $this->generateUrl('app_comptabilite_niveau_etudiant_delete', ['id' => $value]),
                                'ajax' => true,
                                'stacked' => false,
                                'icon' => '%icon% bi bi-trash',
                                'attrs' => ['class' => 'btn-danger'],
                                'render' => $renders['delete']
                            ]*/
                        ]

                    ];
                    return $this->renderView('_includes/default_actions.html.twig', compact('options', 'context'));
                }
            ]);
        }


        $table->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }


        return $this->render('comptabilite/niveau_etudiant/index_valider.html.twig', [
            'datatable' => $table,
            'etat'=>$etat
        ]);
    }


    #[Route('/new', name: 'app_comptabilite_niveau_etudiant_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, FormError $formError): Response
    {
        $niveauEtudiant = new NiveauEtudiant();
        $form = $this->createForm(NiveauEtudiantType::class, $niveauEtudiant, [
            'method' => 'POST',
            'etat'=>'autre',
            'action' => $this->generateUrl('app_comptabilite_niveau_etudiant_new')
        ]);
        $form->handleRequest($request);

        $data = null;
        $statutCode = Response::HTTP_OK;

        $isAjax = $request->isXmlHttpRequest();

        if ($form->isSubmitted()) {
            $response = [];
            $redirect = $this->generateUrl('app_comptabilite_niveau_etudiant_index');




            if ($form->isValid()) {

                $niveauEtudiant->setEtat('ATTENTE_VALIDATION');
                $entityManager->persist($niveauEtudiant);
                $entityManager->flush();

                $data = true;
                $message       = 'Opération effectuée avec succès';
                $statut = 1;
                $this->addFlash('success', $message);


            } else {
                $message = $formError->all($form);
                $statut = 0;
                $statutCode = 500;
                if (!$isAjax) {
                  $this->addFlash('warning', $message);
                }

            }


            if ($isAjax) {
                return $this->json( compact('statut', 'message', 'redirect', 'data'), $statutCode);
            } else {
                if ($statut == 1) {
                    return $this->redirect($redirect, Response::HTTP_OK);
                }
            }


        }

        return $this->render('comptabilite/niveau_etudiant/new.html.twig', [
            'niveau_etudiant' => $niveauEtudiant,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/show', name: 'app_comptabilite_niveau_etudiant_show', methods: ['GET'])]
    public function show(NiveauEtudiant $niveauEtudiant): Response
    {
        return $this->render('comptabilite/niveau_etudiant/show.html.twig', [
            'niveau_etudiant' => $niveauEtudiant,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_comptabilite_niveau_etudiant_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, NiveauEtudiant $niveauEtudiant,NiveauEtudiantRepository $niveauEtudiantRepositoryn, EntityManagerInterface $entityManager, FormError $formError): Response
    {

        $form = $this->createForm(NiveauEtudiantType::class, $niveauEtudiant, [
            'method' => 'POST',
            'etat'=>'autre',
            'action' => $this->generateUrl('app_comptabilite_niveau_etudiant_edit', [
                    'id' =>  $niveauEtudiant->getId()
            ])
        ]);

        $data = null;
        $statutCode = Response::HTTP_OK;

        $isAjax = $request->isXmlHttpRequest();


        $form->handleRequest($request);

       if ($form->isSubmitted()) {
            $response = [];
            $redirect = $this->generateUrl('app_comptabilite_niveau_etudiant_index');

           $workflow = $this->workflow->get($niveauEtudiant, 'preinscription');



            if ($form->isValid()) {

                if ($form->getClickedButton()->getName() === 'passer') {
                    $workflow->apply($niveauEtudiant, 'passer');
                    $niveauEtudiantRepositoryn->add($niveauEtudiant, true);
                } elseif ($form->getClickedButton()->getName() === 'rejeter') {
                    $workflow->apply($niveauEtudiant, 'rejeter');

                    $niveauEtudiantRepositoryn->add($niveauEtudiant, true);
                } else {
                    $niveauEtudiantRepositoryn->add($niveauEtudiant, true);
                }

               /* $entityManager->persist($niveauEtudiant);
                $entityManager->flush();*/

                $data = true;
                $message       = 'Opération effectuée avec succès';
                $statut = 1;
                $this->addFlash('success', $message);


            } else {
                $message = $formError->all($form);
                $statut = 0;
                $statutCode = 500;
                if (!$isAjax) {
                  $this->addFlash('warning', $message);
                }

            }

            if ($isAjax) {
                return $this->json( compact('statut', 'message', 'redirect', 'data'), $statutCode);
            } else {
                if ($statut == 1) {
                    return $this->redirect($redirect, Response::HTTP_OK);
                }
            }

        }

        return $this->render('comptabilite/niveau_etudiant/edit.html.twig', [
            'niveau_etudiant' => $niveauEtudiant,
            'form' => $form->createView(),
            'etudiant' => $niveauEtudiant->getEtudiant(),
        ]);
    }

    #[Route('/{id}/rejeter', name: 'app_comptabilite_niveau_etudiant_rejeter', methods: ['GET', 'POST'])]
    public function rejeter(Request $request, NiveauEtudiant $niveauEtudiant,NiveauEtudiantRepository $niveauEtudiantRepositoryn, EntityManagerInterface $entityManager, FormError $formError): Response
    {

        $form = $this->createForm(NiveauEtudiantType::class, $niveauEtudiant, [
            'method' => 'POST',
            'etat'=>'rejeter',
            'action' => $this->generateUrl('app_comptabilite_niveau_etudiant_rejeter', [
                'id' =>  $niveauEtudiant->getId()
            ])
        ]);

        $data = null;
        $statutCode = Response::HTTP_OK;

        $isAjax = $request->isXmlHttpRequest();


        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $response = [];
            $redirect = $this->generateUrl('app_comptabilite_niveau_etudiant_index');

            $workflow = $this->workflow->get($niveauEtudiant, 'preinscription');



            if ($form->isValid()) {

                if ($form->getClickedButton()->getName() === 'passer') {
                    $workflow->apply($niveauEtudiant, 'passer');
                    $niveauEtudiantRepositoryn->add($niveauEtudiant, true);
                } elseif ($form->getClickedButton()->getName() === 'rejeter') {
                    $workflow->apply($niveauEtudiant, 'rejeter');
                    $niveauEtudiant->setDateValidation(new \DateTime());
                    $niveauEtudiantRepositoryn->add($niveauEtudiant, true);
                } else {
                    $niveauEtudiantRepositoryn->add($niveauEtudiant, true);
                }

                /* $entityManager->persist($niveauEtudiant);
                 $entityManager->flush();*/

                $data = true;
                $message       = 'Opération effectuée avec succès';
                $statut = 1;
                $this->addFlash('success', $message);


            } else {
                $message = $formError->all($form);
                $statut = 0;
                $statutCode = 500;
                if (!$isAjax) {
                    $this->addFlash('warning', $message);
                }

            }

            if ($isAjax) {
                return $this->json( compact('statut', 'message', 'redirect', 'data'), $statutCode);
            } else {
                if ($statut == 1) {
                    return $this->redirect($redirect, Response::HTTP_OK);
                }
            }

        }

        return $this->render('comptabilite/niveau_etudiant/rejeter.html.twig', [
            'niveau_etudiant' => $niveauEtudiant,
            'form' => $form->createView(),
            'etudiant' => $niveauEtudiant->getEtudiant(),
        ]);
    }

    #[Route('/{id}/payer', name: 'app_comptabilite_niveau_etudiant_payer', methods: ['GET', 'POST'])]
    public function payer(Request $request, NiveauEtudiant $niveauEtudiant,NiveauEtudiantRepository $niveauEtudiantRepositoryn, EntityManagerInterface $entityManager, FormError $formError): Response
    {

        $form = $this->createForm(NiveauEtudiantType::class, $niveauEtudiant, [
            'method' => 'POST',
            'etat'=>'payer',
            'action' => $this->generateUrl('app_comptabilite_niveau_etudiant_payer', [
                'id' =>  $niveauEtudiant->getId()
            ])
        ]);

        $data = null;
        $statutCode = Response::HTTP_OK;

        $isAjax = $request->isXmlHttpRequest();


        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $response = [];
            $redirect = $this->generateUrl('app_comptabilite_niveau_etudiant_index');

            $workflow = $this->workflow->get($niveauEtudiant, 'preinscription');



            if ($form->isValid()) {

                if ($form->getClickedButton()->getName() === 'payer') {
                    $workflow->apply($niveauEtudiant, 'payer');

                    $niveauEtudiantRepositoryn->add($niveauEtudiant, true);
                }  else {
                    $niveauEtudiantRepositoryn->add($niveauEtudiant, true);
                }

                /* $entityManager->persist($niveauEtudiant);
                 $entityManager->flush();*/

                $data = true;
                $message       = 'Opération effectuée avec succès';
                $statut = 1;
                $this->addFlash('success', $message);


            } else {
                $message = $formError->all($form);
                $statut = 0;
                $statutCode = 500;
                if (!$isAjax) {
                    $this->addFlash('warning', $message);
                }

            }

            if ($isAjax) {
                return $this->json( compact('statut', 'message', 'redirect', 'data'), $statutCode);
            } else {
                if ($statut == 1) {
                    return $this->redirect($redirect, Response::HTTP_OK);
                }
            }

        }

        return $this->render('comptabilite/niveau_etudiant/payer.html.twig', [
            'niveau_etudiant' => $niveauEtudiant,
            'form' => $form->createView(),
            'etudiant' => $niveauEtudiant->getEtudiant(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_comptabilite_niveau_etudiant_delete', methods: ['DELETE', 'GET'])]
    public function delete(Request $request, NiveauEtudiant $niveauEtudiant, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createFormBuilder()
            ->setAction(
                $this->generateUrl(
                'app_comptabilite_niveau_etudiant_delete'
                ,   [
                        'id' => $niveauEtudiant->getId()
                    ]
                )
            )
            ->setMethod('DELETE')
        ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = true;
            $entityManager->remove($niveauEtudiant);
            $entityManager->flush();

            $redirect = $this->generateUrl('app_comptabilite_niveau_etudiant_index');

            $message = 'Opération effectuée avec succès';

            $response = [
                'statut'   => 1,
                'message'  => $message,
                'redirect' => $redirect,
                'data' => $data
            ];

            $this->addFlash('success', $message);

            if (!$request->isXmlHttpRequest()) {
                return $this->redirect($redirect);
            } else {
                return $this->json($response);
            }
        }

        return $this->render('comptabilite/niveau_etudiant/delete.html.twig', [
            'niveau_etudiant' => $niveauEtudiant,
            'form' => $form,
        ]);
    }
}
